<?php
session_start();
require_once '../config/db_connect.php';

// ป้องกันคนไม่ได้ล็อกอิน หรือไม่ใช่ admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index1.php");
    exit;
}

// ดึงข้อมูลสำหรับ Navbar
$user_stmt = $conn->prepare("SELECT username, profile_image, role FROM userscafe WHERE id = :id");
$user_stmt->execute([':id' => $_SESSION['user_id']]);
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
$is_admin = true;

$msg = '';

// รับค่าการค้นหา (ถ้ามี)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

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
// 3. ระบบดึงข้อมูลมาแสดง (Read) พร้อมเงื่อนไขการค้นหา
// ==========================================
$cat_stmt = $conn->query("SELECT * FROM categories");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// สร้าง Query พื้นฐาน
$sql = "SELECT m.*, c.name as category_name 
        FROM menus m 
        LEFT JOIN categories c ON m.category_id = c.id ";

// ถ้ามีการพิมพ์ค้นหา ให้เพิ่มเงื่อนไข WHERE
if ($search !== '') {
    $sql .= " WHERE m.name LIKE :search ";
}

$sql .= " ORDER BY m.id DESC";

$menu_stmt = $conn->prepare($sql);

if ($search !== '') {
    $menu_stmt->execute([':search' => "%$search%"]);
} else {
    $menu_stmt->execute();
}

$menus = $menu_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการเมนู | Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body { margin: 0; display: flex; flex-direction: column; min-height: 100vh; background-color: var(--color-cream); font-family: 'Prompt', sans-serif; }
        .main-content { flex: 1; padding-bottom: 40px; }
        .container { max-width: 1000px; margin: 40px auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: inline-block; margin-bottom: 5px; font-weight: bold; color: var(--color-brown-dark); }
        .form-control { width: 100%; padding: 8px; border: 1px solid var(--color-brown-light); border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 8px 15px; border: none; border-radius: 4px; color: white; cursor: pointer; text-decoration: none; transition: 0.3s; display: inline-block; }
        .btn:hover { opacity: 0.8; }
        .btn-green { background-color: var(--color-green); }
        .btn-brown { background-color: var(--color-brown-light); }
        
        .checkbox-group { display: flex; gap: 15px; align-items: center; margin-top: 5px; }
        .checkbox-group label { font-weight: normal; cursor: pointer; display: flex; align-items: center; gap: 5px; color: #555; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid var(--color-cream); }
        th, td { padding: 12px 10px; text-align: left; }
        th { background-color: var(--color-brown-light); color: white; }
        img.preview { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #eee; }

        /* สไตล์สำหรับกล่องค้นหา */
        .search-box {
            background-color: #fff9f0;
            padding: 15px;
            border-radius: 8px;
            border: 1px dashed var(--color-brown-light);
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .search-box input { width: 250px; }
    </style>
</head>
<body>
    
    <?php $in_admin = true; include '../navbar.php'; ?>

    <div class="main-content">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; color: var(--color-brown-dark);">☕ จัดการเมนู (เพิ่ม/ลบ/แก้ไข)</h2>
                <a href="dashboard.php" class="btn btn-brown">← กลับ Dashboard</a>
            </div>
            
            <?php echo $msg; ?>

            <div style="background: var(--color-cream); padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <h3 style="margin-top: 0; color: var(--color-brown-dark);">➕ เพิ่มเมนูใหม่</h3>
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
                        <label style="font-weight: normal; cursor: pointer; color: var(--color-brown-dark);">
                            <input type="checkbox" name="is_available" checked> เมนูนี้พร้อมขาย
                        </label>
                    </div>
                    <button type="submit" name="add_menu" class="btn btn-green">💾 บันทึกข้อมูล</button>
                </form>
            </div>

            <h3 style="color: var(--color-brown-dark);">📋 รายการเมนูทั้งหมด</h3>

            <form action="manage_menu.php" method="GET" class="search-box">
                <strong style="color: var(--color-brown-dark);">🔍 ค้นหาเมนู:</strong>
                <input type="text" name="search" class="form-control" placeholder="พิมพ์ชื่อเมนูที่ต้องการค้นหา..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-brown">ค้นหา</button>
                <?php if ($search !== ''): ?>
                    <a href="manage_menu.php" class="btn" style="background-color: #999;">ล้างการค้นหา</a>
                <?php endif; ?>
            </form>

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
                                <span style="color:#999; font-size: 12px;">ไม่มีรูป</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: bold; color: var(--color-brown-dark);"><?php echo $menu['name']; ?></td>
                        <td><?php echo $menu['category_name']; ?></td>
                        <td><span style="background: white; padding: 2px 8px; border-radius: 12px; font-size: 13px; border: 1px solid var(--color-brown-light);"><?php echo $menu['description']; ?></span></td>
                        <td style="color: var(--color-green); font-weight: bold;"><?php echo number_format($menu['price'], 2); ?> ฿</td>
                        <td>
                            <?php echo $menu['is_available'] ? '<span style="color:white; background: #4CAF50; padding: 3px 8px; border-radius: 4px; font-size: 12px;">พร้อมขาย</span>' : '<span style="color:white; background: #f44336; padding: 3px 8px; border-radius: 4px; font-size: 12px;">หมด</span>'; ?>
                        </td>
                        <td>
                            <a href="edit_menu.php?id=<?php echo $menu['id']; ?>" class="btn btn-brown" style="padding: 6px 10px; font-size: 13px;">✏️ แก้ไข</a>
                            
                            <a href="manage_menu.php?delete_id=<?php echo $menu['id']; ?>&search=<?php echo urlencode($search); ?>" class="btn" style="background: #d9534f; padding: 6px 10px; font-size: 13px;" onclick="return confirm('แน่ใจหรือไม่ว่าต้องการลบเมนู <?php echo $menu['name']; ?> ?');">🗑️ ลบ</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if(count($menus) == 0): ?>
                        <tr><td colspan="7" style="text-align:center; padding: 30px; color: #888;">
                            <?php echo ($search !== '') ? 'ไม่พบเมนูที่ค้นหา 😥' : 'ยังไม่มีรายการเมนู'; ?>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include '../footer.php'; ?>
</body>
</html>