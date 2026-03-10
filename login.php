<?php
session_start();
require_once 'config/db_connect.php';

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // ดึงข้อมูลผู้ใช้จากฐานข้อมูล
        $stmt = $conn->prepare("SELECT id, username, password, role FROM userscafe WHERE username = :username LIMIT 1");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // ตรวจสอบรหัสผ่าน
        if ($user && password_verify($password, $user['password'])) {
            // สร้าง Session 
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // ถ้าเป็น admin ให้ไปหลังบ้าน ถ้าเป็น user ให้ไปหน้าแรก
            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $error_msg = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง!";
        }
    } else {
        $error_msg = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ | Cafe Menu</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .login-container h2 {
            color: var(--color-brown-dark);
        }

        .form-control {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid var(--color-brown-light);
            border-radius: 4px;
            box-sizing: border-box;
        }

        .btn-login {
            width: 100%;
            padding: 10px;
            margin-top: 15px;
            background-color: var(--color-green);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-login:hover {
            opacity: 0.9;
        }

        .error {
            color: red;
            margin-bottom: 15px;
        }

        .back-link {
            display: block;
            margin-top: 15px;
            color: var(--color-brown-dark);
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <h2>☕ เข้าสู่ระบบ Admin</h2>

        <?php if ($error_msg != ''): ?>
            <div class="error">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="text" name="username" class="form-control" placeholder="ชื่อผู้ใช้งาน" required>
            <input type="password" name="password" class="form-control" placeholder="รหัสผ่าน" required>
            <button type="submit" class="btn-login">ล็อกอิน</button>
        </form>

        <a href="index.php" class="back-link">← กลับหน้าแรก</a>
    </div>

</body>

</html>