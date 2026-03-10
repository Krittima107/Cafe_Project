<?php
session_start();
require_once 'config/db_connect.php';

// ถ้าไม่ได้ล็อกอิน ให้เด้งไปหน้า login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = '';

// ดึงข้อมูลปัจจุบันของผู้ใช้
$stmt = $conn->prepare("SELECT * FROM userscafe WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// เมื่อกดปุ่มอัปเดตโปรไฟล์
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $new_password = $_POST['new_password'];
    $image_name = $user['profile_image'];

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileTmpPath = $_FILES['profile_image']['tmp_name'];
        $fileName = $_FILES['profile_image']['name'];
        $fileSize = $_FILES['profile_image']['size'];
        $fileType = mime_content_type($fileTmpPath);

        if (in_array($fileType, $allowedTypes) && $fileSize <= 2 * 1024 * 1024) {
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = "profile_" . $user_id . "_" . uniqid() . "." . $fileExtension;

            // สังเกตว่าพาธตัด ../ ออก เพราะไฟล์นี้อยู่หน้าสุดแล้ว
            $upload_dir = "uploads/profiles/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $destPath = $upload_dir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $image_name = $newFileName;
                if (!empty($user['profile_image']) && file_exists($upload_dir . $user['profile_image'])) {
                    unlink($upload_dir . $user['profile_image']);
                }
            } else {
                $msg = "<div style='color:red;'>อัปโหลดรูปไม่สำเร็จ (ติด Permission)</div>";
            }
        } else {
            $msg = "<div style='color:red;'>อนุญาตเฉพาะไฟล์ JPG/PNG ขนาดไม่เกิน 2MB</div>";
        }
    }

    if (empty($msg)) {
        try {
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE userscafe SET username = :username, password = :password, profile_image = :profile_image WHERE id = :id";
                $update_stmt = $conn->prepare($sql);
                $update_stmt->execute([':username' => $new_username, ':password' => $hashed_password, ':profile_image' => $image_name, ':id' => $user_id]);
            } else {
                $sql = "UPDATE userscafe SET username = :username, profile_image = :profile_image WHERE id = :id";
                $update_stmt = $conn->prepare($sql);
                $update_stmt->execute([':username' => $new_username, ':profile_image' => $image_name, ':id' => $user_id]);
            }

            $_SESSION['username'] = $new_username;
            $msg = "<div style='color:green;'>✅ อัปเดตโปรไฟล์สำเร็จ!</div>";

            $stmt->execute([':id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $msg = "<div style='color:red;'>ชื่อผู้ใช้นี้อาจมีคนใช้แล้ว หรือเกิดข้อผิดพลาด</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ตั้งค่าโปรไฟล์ | User</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: var(--color-brown-dark);
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--color-brown-light);
            border-radius: 4px;
            box-sizing: border-box;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-green {
            background-color: var(--color-green);
        }

        .btn-brown {
            background-color: var(--color-brown-light);
        }

        .profile-pic-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--color-brown-light);
            display: block;
            margin: 0 auto 20px auto;
        }

        .status-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: #4CAF50;
            border-radius: 50%;
            margin-right: 5px;
            box-shadow: 0 0 5px #4CAF50;
        }
    </style>
</head>

<body style="background-color: var(--color-cream); font-family: 'Prompt', sans-serif;">
    <div class="container">
        <h2 style="text-align: center; color: var(--color-brown-dark);">⚙️ ตั้งค่าบัญชีผู้ใช้</h2>
        <div style="text-align: center; margin-bottom: 20px; color: #666;">
            <span class="status-dot"></span> กำลังออนไลน์
        </div>

        <?php echo $msg; ?>

        <form action="user_edit_profile.php" method="POST" enctype="multipart/form-data">

            <?php if (!empty($user['profile_image'])): ?>
                <img src="uploads/profiles/<?php echo $user['profile_image']; ?>" class="profile-pic-preview">
            <?php else: ?>
                <div
                    style="width: 120px; height: 120px; border-radius: 50%; background-color: #ddd; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; font-size: 40px;">
                    👤</div>
            <?php endif; ?>

            <div class="form-group">
                <label>เปลี่ยนรูปโปรไฟล์ (JPG, PNG):</label>
                <input type="file" name="profile_image" class="form-control" accept="image/jpeg, image/png">
            </div>

            <div class="form-group">
                <label>ชื่อผู้ใช้งาน (Username):</label>
                <input type="text" name="username" class="form-control"
                    value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>

            <div class="form-group">
                <label>รหัสผ่านใหม่ <span
                        style="font-weight: normal; font-size: 12px; color: #888;">(เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน)</span>:</label>
                <input type="password" name="new_password" class="form-control" placeholder="กรอกรหัสผ่านใหม่...">
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" name="update_profile" class="btn btn-green">💾 บันทึกการเปลี่ยนแปลง</button>
                <a href="index1.php" class="btn btn-brown" style="margin-left: 10px;">← กลับหน้าแรก</a>
            </div>
        </form>
    </div>
</body>

</html>