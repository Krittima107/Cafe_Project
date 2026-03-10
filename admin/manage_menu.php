<?php
session_start();
require_once '../config/db_connect.php';

// ป้องกันคนไม่ได้ล็อกอิน หรือไม่ใช่ admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$msg = '';

// ==========================================
// 1. ระบบลบข้อมูล (Delete) - ทำงานเมื่อกดปุ่มลบ
// ==========================================
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // ดึงชื่อไฟล์รูปภาพออกมาก่อนเพื่อที่จะตามไปลบไฟล์ในโฟลเดอร์
    $stmt_img = $conn->prepare("SELECT image_name FROM menus WHERE id = :id");
    $stmt_img->bindParam(':id', $delete_id);
    $stmt_img->execute();
    $img_row = $stmt_img->fetch(PDO::FETCH_ASSOC);

    // ถ้ามีรูป ให้ลบไฟล์รูปออกจากโฟลเดอร์ด้วย
    if ($img_row && !empty($img_row['image_name'])) {
        $file_path = "../uploads/menus/" . $img_row['image_name'];
        if (file_exists($file_path)) {
            unlink($file_path); 
        }
    }

    // ลบข้อมูลออกจากฐานข้อมูล
    $stmt_del = $conn->prepare("DELETE FROM menus WHERE id = :id");
    $stmt_del->bindParam(':id', $delete_id);
    if ($stmt_del->execute()) {
        $msg = "<div style='color:green; padding: 10px; background: #e8f5e9; border-radius: 4px; margin-bottom: 15px;'>🗑️ ลบเมนูและรูปภาพเรียบร้อยแล้ว!</div>";
    }
}

// ==========================================
// 2. ระบบเพิ่มข้อมูล (Create) - ทำงานเมื่อกดปุ่มบันทึก
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_menu'])) {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    
    // จัดการ Checkbox ร้อน/เย็น/ปั่น
    $serve_type = isset($_POST['serve_type']) ? implode(", ", $_POST['serve_type']) : '-'; 
    $description = $serve_type; 
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    // ระบบจัดการอัปโหลดรูปภาพ
    $image_name = '';
    if (isset($_FILES['menu_image']) && $_FILES['menu_image']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileTmpPath = $_FILES['menu_image']['tmp_name'];
        $fileName = $_FILES['menu_image']['name'];
        $fileSize = $_FILES['menu_image']['size'];
        $fileType = mime_content_type($fileTmpPath);

        if (in_array($fileType, $allowedTypes) && $fileSize <= 2 * 1024 * 1024) {
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = "menu_" . uniqid() . "." . $fileExtension;
            
            // เช็คและสร้างโฟลเดอร์อัตโนมัติ
            $upload_dir = "../uploads/menus/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); 
            }

            $destPath = $upload_dir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $image_name = $newFileName;
            } else {
                $msg = "<div style='color:red;'>อัปโหลดรูปไม่สำเร็จ กรุณาลองใหม่</div>";
            }
        } else {
            $msg = "<div style='color:red;'>ไฟล์ต้องเป็น JPG, PNG และขนาดไม่เกิน 2MB เท่านั้น</div>";
        }
    }

    // บันทึกลงฐานข้อมูล
    if (empty($msg)) {
        try {
            $sql = "INSERT INTO menus (category_id, name, description, price, image_name, is_available) 
                    VALUES (:category_id, :name, :description, :price, :image_name, :is_available)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':category_id' => $category_id,
                ':name' => $name,
                ':description' => $description,
                ':price' => $price,
                ':image_name' => $image_name,
                ':is_available' => $is_available
            ]);
            $msg = "<div style='color:green; padding: 10px; background: #e8f5e9; border-radius: 4px; margin-bottom: 15px;'>✅ เพิ่มเมนูสำเร็จ!</div>";
        } catch(PDOException $e) {
            $msg = "<div style='color:red;'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// ==========================================
// 3. ระบบดึงข้อมูลมาแสดง (Read)
// ==========================================
$cat_stmt = $conn->query("SELECT * FROM categories");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

$menu_stmt = $conn->query("
    SELECT m.*, c.name as category_name 
    FROM menus m 
    LEFT JOIN categories c ON m.category_id = c.id 
    ORDER BY m.id DESC
");
$menus = $menu_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการเมนู | Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .container { max-width: 1000px; margin: 20px auto; padding: 20px; background: white; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: inline-block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 8px; border: 1px solid var(--color-brown-light); border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 8px 15px; border: none; border-radius: 4px; color: white; cursor: pointer; text-decoration: none; }
        .btn-green { background-color: var(--color-green); }
        .btn-brown { background-color: var(--color-brown-light); }
        
        .checkbox-group { display: flex; gap: 15px; align-items: center; margin-top: 5px; }
        .checkbox-group label { font-weight: normal; cursor: pointer; display: flex; align-items: center; gap: 5px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid var(--color-cream); }
        th, td { padding: 10px; text-align: left; }
        th { background-color: var(--color-brown-light); color: white; }
        img.preview { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="btn btn-brown" style="float: right;">← กลับ Dashboard</a>
        <h2>☕ จัดการเมนู (เพิ่ม/ลบ/แก้ไข)</h2>
        <?php echo $msg; ?>

        <div style="background: var(--color-cream); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h3>➕ เพิ่มเมนูใหม่</h3>
            <form action="manage_menu.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>ชื่อเมนู:</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>หมวดหมู่:</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>รูปแบบการเสิร์ฟ:</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="serve_type[]" value="ร้อน"> ร้อน</label>
                        <label><input type="checkbox" name="serve_type[]" value="เย็น"> เย็น</label>
                        <label><input type="checkbox" name="serve_type[]" value="ปั่น"> ปั่น</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>ราคาเริ่มต้น (บาท):</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>รูปภาพ (JPG, PNG ไม่เกิน 2MB):</label>
                    <input type="file" name="menu_image" class="form-control" accept="image/jpeg, image/png">
                </div>
                <div class="form-group">
                    <label style="font-weight: normal; cursor: pointer;"><input type="checkbox" name="is_available" checked> เมนูนี้พร้อมขาย</label>
                </div>
                <button type="submit" name="add_menu" class="btn btn-green">บันทึกข้อมูล</button>
            </form>
        </div>

        <h3>📋 รายการเมนูทั้งหมด</h3>
        <table>
            <thead>
                <tr>
                    <th>รูปภาพ</th>
                    <th>ชื่อเมนู</th>
                    <th>หมวดหมู่</th>
                    <th>รูปแบบ</th>
                    <th>ราคา</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($menus as $menu): ?>
                <tr>
                    <td>
                        <?php if($menu['image_name']): ?>
                            <img src="../uploads/menus/<?php echo $menu['image_name']; ?>" class="preview">
                        <?php else: ?>
                            <span style="color:#999;">ไม่มีรูป</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $menu['name']; ?></td>
                    <td><?php echo $menu['category_name']; ?></td>
                    <td><span style="background: white; padding: 2px 8px; border-radius: 12px; font-size: 14px; border: 1px solid var(--color-brown-light);"><?php echo $menu['description']; ?></span></td>
                    <td><?php echo number_format($menu['price'], 2); ?> ฿</td>
                    <td><?php echo $menu['is_available'] ? '<span style="color:green;">พร้อมขาย</span>' : '<span style="color:red;">หมด</span>'; ?></td>
                    <td>
                        <a href="edit_menu.php?id=<?php echo $menu['id']; ?>" class="btn btn-brown" style="padding: 4px 8px; font-size: 14px;">แก้ไข</a>
                        
                        <a href="manage_menu.php?delete_id=<?php echo $menu['id']; ?>" class="btn" style="background: red; padding: 4px 8px; font-size: 14px;" onclick="return confirm('แน่ใจหรือไม่ว่าต้องการลบเมนูนี้?');">ลบ</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($menus) == 0): ?>
                    <tr><td colspan="7" style="text-align:center;">ยังไม่มีรายการเมนู</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>