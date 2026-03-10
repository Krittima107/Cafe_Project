<?php
session_start();
// ป้องกันคนไม่ได้ล็อกอิน หรือไม่ใช่ admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
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
            margin-right: 10px;
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
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>⚙️ ระบบจัดการร้าน (Dashboard)</h2>
            <div>
                <span style="margin-right: 15px;">👤 แอดมิน:
                    <?php echo $_SESSION['username']; ?>
                </span>
                <a href="../logout.php" class="btn btn-red">ออกจากระบบ</a>
            </div>
        </div>

        <p>ยินดีต้อนรับเข้าสู่ระบบหลังบ้านครับ เดี๋ยวเราจะมาทำระบบเพิ่ม/ลบเมนู และหน้าสรุปยอดขายกันตรงนี้</p>

        <div style="margin-top: 20px;">
            <a href="manage_menu.php" class="btn btn-green">จัดการเมนู</a>
            <a href="../index1.php" class="btn btn-brown">ดูหน้าร้าน</a>
        </div>
    </div>
</body>

</html>