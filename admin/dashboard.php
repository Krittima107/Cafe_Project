<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index1.php");
    exit;
}

$user_stmt = $conn->prepare("SELECT username, profile_image, role FROM userscafe WHERE id = :id");
$user_stmt->execute([':id' => $_SESSION['user_id']]);
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
$is_admin = true; // บอก Navbar ว่าเราเป็นแอดมิน

$stmt1 = $conn->query("SELECT COUNT(*) FROM menus");
$total_menus = $stmt1->fetchColumn();
$stmt2 = $conn->query("SELECT COUNT(*) FROM categories");
$total_categories = $stmt2->fetchColumn();
$stmt3 = $conn->query("SELECT COUNT(*) FROM userscafe");
$total_users = $stmt3->fetchColumn();
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
        body {
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: var(--color-cream);
            font-family: 'Prompt', sans-serif;
        }

        .main-content {
            flex: 1;
            padding-bottom: 40px;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .header {
            border-bottom: 2px solid var(--color-cream);
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .btn {
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            color: white;
            display: inline-block;
            transition: 0.3s;
        }

        .btn:hover {
            opacity: 0.8;
        }

        .btn-green {
            background-color: var(--color-green);
        }

        .btn-brown {
            background-color: var(--color-brown-light);
        }

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

    <?php
    $in_admin = true;
    include '../navbar.php';
    ?>

    <div class="main-content">
        <div class="container">
            <div class="header">
                <h2 style="margin: 0; color: var(--color-brown-dark);">⚙️ ระบบจัดการร้าน (Dashboard)</h2>
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
    </div>

    <?php include '../footer.php'; ?>

</body>

</html>