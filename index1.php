<?php
session_start();
require_once 'config/db_connect.php';

// เช็คสถานะว่าตอนนี้ใครใช้งานอยู่ (แต่ไม่บังคับล็อกอิน)
$is_admin = false;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    $is_admin = true;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Cafe Menu | หน้าแรก</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* สไตล์พื้นฐานสำหรับ Navbar หน้าร้าน */
        .navbar {
            background-color: var(--color-brown-light);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            background-color: var(--color-brown-dark);
            border-radius: 4px;
        }

        .navbar a:hover {
            background-color: var(--color-green);
        }

        .content {
            padding: 20px;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="navbar">
        <h2>☕ Cafe Menu</h2>
        <div>
            <?php if ($is_admin): ?>
                <a href="admin/dashboard.php">⚙️ จัดการระบบ (Dashboard)</a>
                <a href="logout.php" style="background-color: #d9534f;">ออกจากระบบ</a>
            <?php else: ?>
                <a href="login.php">🔒 สำหรับผู้ดูแลระบบ</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="content">
        <h1>ยินดีต้อนรับสู่ร้านกาแฟของเรา</h1>
        <p>รายการเมนูแสนอร่อยกำลังจะมาแสดงที่นี่...</p>
    </div>

</body>

</html>