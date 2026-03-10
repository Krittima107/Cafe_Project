<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$msg = '';

// เช็คว่ามีการส่ง ID มาหรือไม่
if (!isset($_GET['id'])) {
    header("Location: manage_menu.php");
    exit;
}
$menu_id = $_GET['id'];

// --- ส่วนจัดการเมื่อกดปุ่ม "อัปเดตข้อมูล" ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_menu'])) {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];

    // จัดการ Checkbox ร้อน/เย็น/ปั่น
    $serve_type = isset($_POST['serve_type']) ? implode(", ", $_POST['serve_type']) : '-';
    $description = $serve_type;
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    // จัดการรูปภาพ (ถ้ามีการอัปโหลดใหม่)
    $image_name = $_POST['old_image']; // ค่าเริ่มต้นใช้รูปเดิม
    if (isset($_FILES['menu_image']) && $_FILES['menu_image']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileTmpPath = $_FILES['menu_image']['tmp_name'];
        $fileName = $_FILES['menu_image']['name'];
        $fileSize = $_FILES['menu_image']['size'];
        $fileType = mime_content_type($fileTmpPath);

        if (in_array($fileType, $allowedTypes) && $fileSize <= 2 * 1024 * 1024) {
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = "menu_" . uniqid() . "." . $fileExtension;
            $upload_dir = "../uploads/menus/";
            $destPath = $upload_dir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $image_name = $newFileName; // อัปเดตชื่อไฟล์ใหม่
                // ลบรูปภาพเก่าทิ้งเพื่อประหยัดพื้นที่
                if (!empty($_POST['old_image']) && file_exists($upload_dir . $_POST['old_image'])) {
                    unlink($upload_dir . $_POST['old_image']);
                }
            } else {
                $msg = "<div style='color:red;'>อัปโหลดรูปไม่สำเร็จ</div>";
            }
        } else {
            $msg = "<div style='color:red;'>ไฟล์ต้องเป็น JPG, PNG และขนาดไม่เกิน 2MB เท่านั้น</div>";
        }
    }

    // อัปเดตข้อมูลลงฐานข้อมูล
    if (empty($msg)) {
        try {
            $sql = "UPDATE menus SET category_id = :category_id, name = :name, description = :description, 
                    price = :price, image_name = :image_name, is_available = :is_available 
                    WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':category_id' => $category_id,
                ':name' => $name,
                ':description' => $description,
                ':price' => $price,
                ':image_name' => $image_name,
                ':is_available' => $is_available,
                ':id' => $menu_id
            ]);
            $msg = "<div style='color:green;'>✅ อัปเดตข้อมูลสำเร็จ! กำลังกลับไปหน้าจัดการ...</div>";
            header("Refresh: 1.5; url=manage_menu.php"); // เด้งกลับหน้าเดิมหลังผ่านไป 1.5 วินาที
        } catch (PDOException $e) {
            $msg = "<div style='color:red;'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// --- ดึงข้อมูลปัจจุบันมาแสดงในฟอร์ม ---
$stmt = $conn->prepare("SELECT * FROM menus WHERE id = :id");
$stmt->bindParam(':id', $menu_id);
$stmt->execute();
$menu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$menu) {
    echo "ไม่พบข้อมูลเมนูนี้";
    exit;
}

// แยกคำ ร้อน/เย็น/ปั่น ออกมาเพื่อติ๊ก Checkbox ให้ตรง
$serve_types = explode(", ", $menu['description']);

// ดึงหมวดหมู่สำหรับ Dropdown
$cat_stmt = $conn->query("SELECT * FROM categories");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>แก้ไขเมนู | Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: inline-block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--color-brown-light);
            border-radius: 4px;
            box-sizing: border-box;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-green {
            background-color: var(--color-green);
        }

        .btn-brown {
            background-color: var(--color-brown-light);
        }

        .checkbox-group {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-top: 5px;
        }

        .checkbox-group label {
            font-weight: normal;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        img.preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 10px;
            border: 2px solid var(--color-cream);
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>✏️ แก้ไขข้อมูลเมนู</h2>
        <?php echo $msg; ?>

        <div style="background: var(--color-cream); padding: 15px; border-radius: 8px; margin-top: 15px;">
            <form action="edit_menu.php?id=<?php echo $menu['id']; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>ชื่อเมนู:</label>
                    <input type="text" name="name" class="form-control"
                        value="<?php echo htmlspecialchars($menu['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>หมวดหมู่:</label>
                    <select name="category_id" class="form-control" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $menu['category_id']) ? 'selected' : ''; ?>>
                                <?php echo $cat['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>รูปแบบการเสิร์ฟ:</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="serve_type[]" value="ร้อน" <?php echo in_array('ร้อน', $serve_types) ? 'checked' : ''; ?>> ร้อน</label>
                        <label><input type="checkbox" name="serve_type[]" value="เย็น" <?php echo in_array('เย็น', $serve_types) ? 'checked' : ''; ?>> เย็น</label>
                        <label><input type="checkbox" name="serve_type[]" value="ปั่น" <?php echo in_array('ปั่น', $serve_types) ? 'checked' : ''; ?>> ปั่น</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>ราคาเริ่มต้น (บาท):</label>
                    <input type="number" step="0.01" name="price" class="form-control"
                        value="<?php echo $menu['price']; ?>" required>
                </div>

                <div class="form-group">
                    <label>รูปภาพปัจจุบัน:</label><br>
                    <?php if ($menu['image_name']): ?>
                        <img src="../uploads/menus/<?php echo $menu['image_name']; ?>" class="preview">
                    <?php else: ?>
                        <p style="color:#999;">ยังไม่มีรูปภาพ</p>
                    <?php endif; ?>
                    <input type="hidden" name="old_image" value="<?php echo $menu['image_name']; ?>">
                </div>

                <div class="form-group">
                    <label>เปลี่ยนรูปภาพใหม่ (ไม่เปลี่ยนไม่ต้องเลือก):</label>
                    <input type="file" name="menu_image" class="form-control" accept="image/jpeg, image/png">
                </div>

                <div class="form-group">
                    <label style="font-weight: normal; cursor: pointer;">
                        <input type="checkbox" name="is_available" <?php echo $menu['is_available'] ? 'checked' : ''; ?>> เมนูนี้พร้อมขาย
                    </label>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" name="update_menu" class="btn btn-green">อัปเดตข้อมูล</button>
                    <a href="manage_menu.php" class="btn btn-brown" style="margin-left: 10px;">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>