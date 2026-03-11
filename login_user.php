<?php
session_start();
require_once 'config/db_connect.php';

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM userscafe WHERE username = :username LIMIT 1");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] !== 'user') {
                $error_msg = "บัญชีนี้เป็น Admin กรุณาไปเข้าสู่ระบบที่หน้า Admin ครับ!";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header("Location: index1.php");
                exit;
            }
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
    <title>เข้าสู่ระบบ User | Moom Marm Cafe</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            font-family: 'Prompt', sans-serif;
        }

        /* สร้างพื้นหลังสีเต็มจอแบบ Shopee */
        .login-wrapper {
            flex: 1;
            background-color: var(--color-brown-light);
            /* สีธีมคาเฟ่ */
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        /* กล่องครอบเลย์เอาต์ซ้าย-ขวา */
        .login-split-container {
            display: flex;
            max-width: 1000px;
            width: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 40px;
            flex-wrap: wrap;
            /* ถ้าจอมือถือจะปัดลงมาเรียงแนวตั้ง */
        }

        /* ส่วนแบรนด์ (โลโก้ซ้ายมือ) */
        .login-brand {
            flex: 1;
            min-width: 300px;
            text-align: center;
            color: white;
        }

        .login-brand img {
            width: 250px;
            height: 250px;
            object-fit: contain;
            background-color: white;
            /* พื้นหลังขาววงกลมให้โลโก้เด่น */
            border-radius: 50%;
            padding: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            margin-bottom: 25px;
        }

        .login-brand h1 {
            margin: 0;
            font-size: 36px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        }

        .login-brand p {
            font-size: 18px;
            margin-top: 10px;
            opacity: 0.9;
        }

        /* ส่วนกล่องล็อกอิน (ขวามือ) */
        .login-box {
            flex: 0 0 400px;
            /* ล็อกขนาดกล่องขวา */
            max-width: 100%;
            background: white;
            padding: 40px 30px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            box-sizing: border-box;
        }

        .login-box h2 {
            color: var(--color-green);
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
            border-color: var(--color-green);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            margin-top: 15px;
            background-color: var(--color-green);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-login:hover {
            background-color: #3d8b40;
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
                <img src="assets/logo.png" alt="Moom Marm Cafe">
                <h1>Moom Marm Cafe</h1>
                <p>มุมโปรดของคนรักกาแฟและเบเกอรี่</p>
            </div>

            <div class="login-box">
                <h2>เข้าสู่ระบบ</h2>

                <?php if ($error_msg != ''): ?>
                    <div class="error"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <form action="login_user.php" method="POST">
                    <input type="text" name="username" class="form-control" placeholder="ชื่อผู้ใช้งาน (Username)"
                        required>
                    <input type="password" name="password" class="form-control" placeholder="รหัสผ่าน (Password)"
                        required>
                    <button type="submit" class="btn-login">เข้าสู่ระบบ</button>
                </form>

                <div class="divider">หรือ</div>
                <a href="index1.php" class="back-link">กลับไปเลือกดูเมนูหน้าร้าน</a>
            </div>

        </div>
    </div>

    <?php include 'footer.php'; ?>

</body>

</html>