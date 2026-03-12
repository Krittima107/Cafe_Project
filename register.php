<?php
session_start();
require_once 'config/db_connect.php';

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // 1. ตรวจสอบว่ากรอกข้อมูลครบไหม
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error_msg = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
    // 2. ตรวจสอบว่ารหัสผ่านตรงกันไหม
    elseif ($password !== $confirm_password) {
        $error_msg = "รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน!";
    } else {
        // 3. เช็คว่ามี Username นี้ในระบบหรือยัง
        $stmt_check = $conn->prepare("SELECT id FROM userscafe WHERE username = :username LIMIT 1");
        $stmt_check->bindParam(':username', $username);
        $stmt_check->execute();

        if ($stmt_check->rowCount() > 0) {
            $error_msg = "ชื่อผู้ใช้นี้ถูกใช้งานแล้ว กรุณาเลือกชื่ออื่น!";
        } else {
            // 4. สมัครสมาชิกใหม่ (ค่าเริ่มต้นคือ role = 'user')
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            try {
                $stmt_insert = $conn->prepare("INSERT INTO userscafe (username, password, role) VALUES (:username, :password, 'user')");
                $stmt_insert->execute([
                    ':username' => $username,
                    ':password' => $hashed_password
                ]);
                $success_msg = "สมัครสมาชิกสำเร็จ! คุณสามารถเข้าสู่ระบบได้เลย";
            } catch (PDOException $e) {
                $error_msg = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>สมัครสมาชิก | Moom Marm Cafe</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            font-family: 'Prompt', sans-serif;
        }

        /* พื้นหลังสีครีมเหลือง แบบเดียวกับหน้าล็อกอิน */
        .login-wrapper {
            flex: 1;
            background-color: #f5e6c8;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .login-split-container {
            display: flex;
            max-width: 1000px;
            width: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 40px;
            flex-wrap: wrap;
        }

        /* ส่วนแบรนด์โลโก้ด้านซ้าย */
        .login-brand {
            flex: 1;
            min-width: 300px;
            text-align: center;
            color: #8d6e63;
        }

        .login-brand img {
            width: 250px;
            height: 250px;
            object-fit: contain;
            background-color: white;
            border-radius: 50%;
            padding: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        .login-brand h1 {
            margin: 0;
            font-size: 36px;
            text-shadow: none;
        }

        .login-brand p {
            font-size: 18px;
            margin-top: 10px;
            opacity: 1;
        }

        /* กล่องฟอร์มด้านขวา */
        .login-box {
            flex: 0 0 400px;
            max-width: 100%;
            background: white;
            padding: 40px 30px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        .login-box h2 {
            color: var(--color-brown-dark);
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 24px;
            text-align: left;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 15px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--color-brown-dark);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            margin-top: 15px;
            background-color: var(--color-brown-dark);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-login:hover {
            background-color: #5d4037;
        }

        .error {
            color: #d9534f;
            margin-bottom: 15px;
            background: #fdf2f2;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
            border-left: 4px solid #d9534f;
        }

        .success {
            color: #4caf50;
            margin-bottom: 15px;
            background: #e8f5e9;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
            border-left: 4px solid #4caf50;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: #999;
            font-size: 12px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #eee;
        }

        .divider::before {
            margin-right: .5em;
        }

        .divider::after {
            margin-left: .5em;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: var(--color-brown-dark);
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div class="login-wrapper">
        <div class="login-split-container">
            <div class="login-brand">
                <img src="assets/logo.png" alt="Moom Marm Cafe Logo">
                <h1>Moom Marm Cafe</h1>
                <p>สมัครสมาชิกใหม่</p>
            </div>

            <div class="login-box">
                <h2>สร้างบัญชีผู้ใช้</h2>

                <?php if ($error_msg != ''): ?>
                    <div class="error">
                        <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success_msg != ''): ?>
                    <div class="success">
                        <?php echo $success_msg; ?>
                    </div>
                    <a href="login.php" class="btn-login"
                        style="display: block; text-align: center; text-decoration: none; background-color: var(--color-green);">ไปหน้าเข้าสู่ระบบ</a>
                <?php else: ?>

                    <form action="register.php" method="POST">
                        <input type="text" name="username" class="form-control" placeholder="ตั้งชื่อผู้ใช้งาน (Username)"
                            required>
                        <input type="password" name="password" class="form-control" placeholder="ตั้งรหัสผ่าน (Password)"
                            required>
                        <input type="password" name="confirm_password" class="form-control"
                            placeholder="ยืนยันรหัสผ่านอีกครั้ง" required>

                        <button type="submit" class="btn-login">ยืนยันการสมัครสมาชิก</button>
                    </form>

                <?php endif; ?>

                <div class="divider">หรือ</div>
                <a href="login.php" class="back-link">มีบัญชีอยู่แล้ว? เข้าสู่ระบบ</a>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

</body>

</html>