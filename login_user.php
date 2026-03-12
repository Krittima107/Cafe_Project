<?php
session_start();
require_once 'config/db_connect.php';

$error_msg = '';

$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? 'index1.php';

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

                header("Location: " . $redirect);
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
<title>เข้าสู่ระบบ User | Cafe Menu</title>
<link rel="stylesheet" href="assets/style.css">

<style>

body{
display:flex;
flex-direction:column;
min-height:100vh;
margin:0;
background-color:var(--color-cream);
font-family:'Prompt',sans-serif;
}

.main-content{
flex:1;
display:flex;
justify-content:center;
align-items:center;
padding:40px 20px;
}

.login-container{
width:100%;
max-width:400px;
padding:30px;
background-color:white;
border-radius:8px;
box-shadow:0 4px 8px rgba(0,0,0,0.1);
text-align:center;
}

.login-container h2{
color:var(--color-green);
margin-top:0;
}

.form-control{
width:100%;
padding:10px;
margin:10px 0;
border:1px solid var(--color-brown-light);
border-radius:4px;
box-sizing:border-box;
}

.btn-login{
width:100%;
padding:10px;
margin-top:15px;
background-color:var(--color-green);
color:white;
border:none;
border-radius:4px;
cursor:pointer;
font-size:16px;
}

.btn-login:hover{
opacity:0.9;
}

.error{
color:red;
margin-bottom:15px;
}

</style>
</head>

<body>

<?php include 'navbar.php'; ?>

<div class="main-content">

<div class="login-container">

<h2>☕ เข้าสู่ระบบ User ☕</h2>

<?php if ($error_msg != ''): ?>
<div class="error"><?php echo $error_msg; ?></div>
<?php endif; ?>

<form action="login_user.php" method="POST">

<input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

<input type="text" name="username" class="form-control" placeholder="ชื่อผู้ใช้งาน" required>

<input type="password" name="password" class="form-control" placeholder="รหัสผ่าน" required>

<button type="submit" class="btn-login">
ล็อกอิน (User)
</button>

</form>

</div>

</div>

<?php include 'footer.php'; ?>

</body>
</html>