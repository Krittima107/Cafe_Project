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
$is_admin = true;

$stmt = $conn->query("
    SELECT orders.*, userscafe.username
    FROM orders
    JOIN userscafe ON orders.user_id = userscafe.id
    ORDER BY orders.id DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Orders | Admin</title>
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
            max-width: 1100px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            padding: 12px;
            text-align: center;
            border: 1px solid var(--color-brown-light);
        }

        th {
            background: var(--color-cream);
            color: var(--color-brown-dark);
        }

        .btn-view {
            padding: 6px 12px;
            background: #2196f3;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: 0.3s;
        }

        .btn-view:hover {
            opacity: 0.8;
        }

        .btn-back {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 15px;
            background: var(--color-brown-light);
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <?php $in_admin = true;
    include '../navbar.php'; ?>
    <div class="main-content">
        <div class="container">
            <a href="dashboard.php" class="btn-back">← กลับหน้า Dashboard</a>
            <h2 style="margin-bottom: 20px; color: var(--color-brown-dark);">📦 รายการคำสั่งซื้อทั้งหมด</h2>
            <table>
                <tr>
                    <th>Order ID</th>
                    <th>ลูกค้า</th>
                    <th>วันที่สั่งซื้อ</th>
                    <th>ราคารวม</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo $order['username']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                        <td style="color: var(--color-green); font-weight: bold;">
                            <?php echo number_format($order['total_price'], 2); ?> ฿</td>
                        <td>
                            <?php if ($order['status'] == 'completed'): ?>
                                <span style="color: green; background: #e8f5e9; padding: 4px 10px; border-radius: 12px;">✅
                                    เสร็จสิ้น</span>
                            <?php else: ?>
                                <span style="color: orange; background: #fff3e0; padding: 4px 10px; border-radius: 12px;">⏳
                                    รอรับออเดอร์</span>
                            <?php endif; ?>
                        </td>
                        <td><a class="btn-view" href="order_detail.php?id=<?php echo $order['id']; ?>">ดูรายละเอียด</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($orders) == 0): ?>
                    <tr>
                        <td colspan="6">ยังไม่มีคำสั่งซื้อ</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
    <?php include '../footer.php'; ?>
</body>

</html>