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

$order_id = $_GET['id'] ?? 0;

// --- 1. ดึงข้อมูลสถานะออเดอร์มาเช็คก่อน ---
$order_stmt = $conn->prepare("SELECT status FROM orders WHERE id = :id");
$order_stmt->execute([':id' => $order_id]);
$order_info = $order_stmt->fetch(PDO::FETCH_ASSOC);

// --- 2. ดึงรายการสินค้าในออเดอร์ ---
$stmt = $conn->prepare("
    SELECT order_items.*, menus.name
    FROM order_items
    JOIN menus ON order_items.menu_id = menus.id
    WHERE order_items.order_id = :id
");
$stmt->execute([':id' => $order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Order Detail</title>
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
            max-width: 800px;
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

        .total-row {
            font-weight: bold;
            background: #f9f9f9;
            color: var(--color-green);
            font-size: 18px;
        }

        .btn-complete {
            display: inline-block;
            padding: 10px 20px;
            background: #4caf50;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
        }

        .btn-complete:hover {
            background: #388e3c;
        }

        /* สไตล์ป้ายแจ้งเตือนว่าเสร็จสิ้นแล้ว */
        .status-completed-box {
            display: inline-block;
            padding: 10px 20px;
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 2px solid #4caf50;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
        }

        .btn-center {
            text-align: center;
            margin-top: 25px;
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
            <a href="orders.php" class="btn-back">← กลับหน้ารายการสั่งซื้อ</a>
            <h2 style="margin-bottom: 20px; color: var(--color-brown-dark);">📋 รายละเอียด Order
                #<?php echo htmlspecialchars($order_id); ?></h2>

            <table>
                <tr>
                    <th>เมนู</th>
                    <th>ราคาต่อแก้ว</th>
                    <th>จำนวน</th>
                    <th>รวม</th>
                </tr>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td style="text-align: left;"><?php echo $item['name']; ?></td>
                        <td><?php echo number_format($item['price'], 2); ?></td>
                        <td>x <?php echo $item['quantity']; ?></td>
                        <td><?php echo number_format($item['price'] * $item['quantity'], 2); ?> ฿</td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right; padding-right: 20px;">ยอดชำระเงินรวมทั้งสิ้น</td>
                    <td><?php echo number_format($total, 2); ?> ฿</td>
                </tr>
            </table>

            <div class="btn-center">
                <?php
                // เช็คว่าสถานะเป็น completed (เสร็จสิ้น) หรือยัง
                if ($order_info && $order_info['status'] === 'completed'):
                    ?>
                    <div class="status-completed-box">
                        ✅ ออเดอร์นี้รับชำระเงินและเสร็จสิ้นแล้ว
                    </div>

                <?php else: ?>
                    <a href="update_status.php?id=<?php echo $order_id; ?>&status=completed" class="btn-complete"
                        onclick="return confirm('ยืนยันว่าลูกค้ารับสินค้าและชำระเงินเรียบร้อยแล้ว?');">
                        ☑️ ทำเครื่องหมายว่าเสร็จสิ้นแล้ว
                    </a>

                <?php endif; ?>
            </div>

        </div>
    </div>

    <?php include '../footer.php'; ?>
</body>

</html>